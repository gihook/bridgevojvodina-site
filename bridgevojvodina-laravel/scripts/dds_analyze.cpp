/*
   Small JSON CLI wrapper around DDS CalcDDtablePBN.

   Build against the local dds/ source tree and place the binary at:
   storage/app/bin/dds_analyze
*/

#include <api/dll.h>

#include <cstring>
#include <iostream>
#include <string>

namespace {

void print_json_string(const std::string& value)
{
  std::cout << '"';
  for (const char ch : value)
  {
    switch (ch)
    {
      case '\\': std::cout << "\\\\"; break;
      case '"': std::cout << "\\\""; break;
      case '\n': std::cout << "\\n"; break;
      case '\r': std::cout << "\\r"; break;
      case '\t': std::cout << "\\t"; break;
      default: std::cout << ch; break;
    }
  }
  std::cout << '"';
}

int print_error(const int code, const std::string& fallback)
{
  char message[80] = {0};
  if (code != RETURN_UNKNOWN_FAULT)
  {
    ErrorMessage(code, message);
  }

  std::cout << "{\"error_code\":" << code << ",\"error\":";
  print_json_string(message[0] != '\0' ? std::string(message) : fallback);
  std::cout << "}\n";
  return 1;
}

}  // namespace

int main(int argc, char** argv)
{
  if (argc < 2)
  {
    return print_error(RETURN_UNKNOWN_FAULT, "Missing PBN deal argument.");
  }

#if defined(__linux__) || defined(__APPLE__)
  SetMaxThreads(0);
#endif

  DdTableDealPBN deal{};
  const std::string pbn = argv[1];

  if (pbn.size() >= sizeof(deal.cards))
  {
    return print_error(RETURN_PBN_FAULT, "PBN deal is too long.");
  }

  std::memcpy(deal.cards, pbn.c_str(), pbn.size() + 1);

  DdTableResults table{};
  const int result = CalcDDtablePBN(deal, &table);
  if (result != RETURN_NO_FAULT)
  {
    return print_error(result, "DDS table calculation failed.");
  }

  const char* strains[DDS_STRAINS] = {"S", "H", "D", "C", "NT"};

  std::cout << "{\"engine\":\"dds\",\"table\":{";
  for (int strain = 0; strain < DDS_STRAINS; ++strain)
  {
    if (strain > 0)
    {
      std::cout << ',';
    }

    print_json_string(strains[strain]);
    std::cout << ":[";

    for (int hand = 0; hand < DDS_HANDS; ++hand)
    {
      if (hand > 0)
      {
        std::cout << ',';
      }
      std::cout << table.res_table[strain][hand];
    }

    std::cout << ']';
  }
  std::cout << "}}\n";

  return 0;
}
