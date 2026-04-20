// airports-data.js — Aero Greet India
// USD pricing. dom/intl=null means not available.
// BLR & HYD: dom=95, intl=120. All others: dom=70, intl=85.
// Porter=15, Wheelchair=15.

var AIRPORTS = [
  {code:"IXA",city:"Agartala",    name:"Agartala Airport",                              state:"Tripura",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1570710891163-6d3b5c47248b?w=1400&q=80"},
  {code:"AGR",city:"Agra",        name:"Agra Airport",                                  state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1564507592333-c60657eea523?w=1400&q=80"},
  {code:"AMD",city:"Ahmedabad",   name:"Sardar Vallabhbhai Patel International Airport",state:"Gujarat",           dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"AJL",city:"Aizawl",      name:"Lengpui Airport",                               state:"Mizoram",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"AVT",city:"Amravati",    name:"Amravati Airport",                              state:"Maharashtra",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"ATQ",city:"Amritsar",    name:"Sri Guru Ram Dass Jee International Airport",   state:"Punjab",            dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1555400038-63f5ba517a47?w=1400&q=80"},
  {code:"IXU",city:"Aurangabad",  name:"Aurangabad Airport",                            state:"Maharashtra",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"AYJ",city:"Ayodhya",     name:"Maharishi Valmiki International Airport",       state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"IXB",city:"Bagdogra",    name:"Bagdogra International Airport",                state:"West Bengal",       dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"BEK",city:"Bareilly",    name:"Bareilly Airport",                              state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"IXG",city:"Belagavi",    name:"Belagavi Airport",                              state:"Karnataka",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"BLR",city:"Bengaluru",   name:"Kempegowda International Airport",              state:"Karnataka",         dom:95, intl:120,  porter:15,buggy:15, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"BHO",city:"Bhopal",      name:"Raja Bhoj Airport",                             state:"Madhya Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1514222134-b57cbb8ce073?w=1400&q=80"},
  {code:"BBI",city:"Bhubaneswar", name:"Biju Patnaik International Airport",            state:"Odisha",            dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1548013146-72479768bada?w=1400&q=80"},
  {code:"BKB",city:"Bikaner",     name:"Bikaner Airport",                               state:"Rajasthan",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"IXC",city:"Chandigarh",  name:"Chandigarh International Airport",              state:"Punjab/Haryana",    dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1555400038-63f5ba517a47?w=1400&q=80"},
  {code:"MAA",city:"Chennai",     name:"Chennai International Airport",                 state:"Tamil Nadu",        dom:70, intl:85,  porter:15,buggy:15, img:"https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1400&q=80"},
  {code:"CJB",city:"Coimbatore",  name:"Coimbatore International Airport",              state:"Tamil Nadu",        dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"DBR",city:"Darbhanga",   name:"Darbhanga Airport",                             state:"Bihar",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"DEL",city:"Delhi",       name:"Indira Gandhi International Airport",           state:"Delhi",             dom:70, intl:85,  porter:15,buggy:15, img:"https://images.unsplash.com/photo-1587474260584-136574528ed5?w=1400&q=80"},
  {code:"DGH",city:"Deoghar",     name:"Deoghar Airport",                               state:"Jharkhand",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"DHM",city:"Dharamshala", name:"Kangra Airport",                                state:"Himachal Pradesh",  dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"DIB",city:"Dibrugarh",   name:"Dibrugarh Airport",                             state:"Assam",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"DMU",city:"Dimapur",     name:"Dimapur Airport",                               state:"Nagaland",          dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"DIU",city:"Diu",         name:"Diu Airport",                                   state:"Daman & Diu",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1400&q=80"},
  {code:"RDP",city:"Durgapur",    name:"Kazi Nazrul Islam Airport",                     state:"West Bengal",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"GAY",city:"Gaya",        name:"Gaya Airport",                                  state:"Bihar",             dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"GOI",city:"Goa",         name:"Dabolim Airport",                               state:"Goa",               dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?w=1400&q=80"},
  {code:"GOP",city:"Gorakhpur",   name:"Gorakhpur Airport",                             state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"GAU",city:"Guwahati",    name:"Lokpriya Gopinath Bordoloi International Airport",state:"Assam",           dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"GWL",city:"Gwalior",     name:"Rajmata Vijaya Raje Scindia Airport",           state:"Madhya Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1514222134-b57cbb8ce073?w=1400&q=80"},
  {code:"HSR",city:"Hirasar",     name:"Rajkot International Airport (Hirasar)",        state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"HBX",city:"Hubli",       name:"Hubballi Airport",                              state:"Karnataka",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"HYD",city:"Hyderabad",   name:"Rajiv Gandhi International Airport",            state:"Telangana",         dom:95, intl:120,  porter:15,buggy:15, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"IMF",city:"Imphal",      name:"Bir Tikendrajit International Airport",         state:"Manipur",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"IDR",city:"Indore",      name:"Devi Ahilyabai Holkar International Airport",   state:"Madhya Pradesh",    dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1514222134-b57cbb8ce073?w=1400&q=80"},
  {code:"HGI",city:"Itanagar",    name:"Donyi Polo Airport",                            state:"Arunachal Pradesh", dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"JLR",city:"Jabalpur",    name:"Jabalpur Airport",                              state:"Madhya Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1514222134-b57cbb8ce073?w=1400&q=80"},
  {code:"JGB",city:"Jagdalpur",   name:"Jagdalpur Airport",                             state:"Chhattisgarh",      dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"JAI",city:"Jaipur",      name:"Jaipur International Airport",                  state:"Rajasthan",         dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"IXJ",city:"Jammu",       name:"Jammu Airport",                                 state:"J&K",               dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"JRG",city:"Jharsuguda",  name:"Veer Surendra Sai Airport",                     state:"Odisha",            dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1548013146-72479768bada?w=1400&q=80"},
  {code:"JDH",city:"Jodhpur",     name:"Jodhpur Airport",                               state:"Rajasthan",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"JRH",city:"Jorhat",      name:"Jorhat Airport",                                state:"Assam",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"CDP",city:"Kadapa",      name:"Kadapa Airport",                                state:"Andhra Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"IXY",city:"Kandla",      name:"Kandla Airport",                                state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"CNN",city:"Kannur",      name:"Kannur International Airport",                  state:"Kerala",            dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?w=1400&q=80"},
  {code:"KNU",city:"Kanpur",      name:"Kanpur Airport",                                state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"IXK",city:"Keshod",      name:"Keshod Airport",                                state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"HJR",city:"Khajuraho",   name:"Khajuraho Airport",                             state:"Madhya Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1514222134-b57cbb8ce073?w=1400&q=80"},
  {code:"KQH",city:"Kishangarh",  name:"Kishangarh Airport",                            state:"Rajasthan",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"COK",city:"Kochi",       name:"Cochin International Airport",                  state:"Kerala",            dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?w=1400&q=80"},
  {code:"KLH",city:"Kolhapur",    name:"Kolhapur Airport",                              state:"Maharashtra",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"CCU",city:"Kolkata",     name:"Netaji Subhash Chandra Bose International Airport",state:"West Bengal",    dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1400&q=80"},
  {code:"CCJ",city:"Kozhikode",   name:"Calicut International Airport",                 state:"Kerala",            dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?w=1400&q=80"},
  {code:"KJB",city:"Kurnool",     name:"Kurnool Airport",                               state:"Andhra Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"IXL",city:"Leh",         name:"Kushok Bakula Rimpochee Airport",               state:"Ladakh",            dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"IXI",city:"Lilabari",    name:"Lilabari Airport",                              state:"Assam",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"LKO",city:"Lucknow",     name:"Chaudhary Charan Singh International Airport",  state:"Uttar Pradesh",     dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1587474260584-136574528ed5?w=1400&q=80"},
  {code:"IXM",city:"Madurai",     name:"Madurai Airport",                               state:"Tamil Nadu",        dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1400&q=80"},
  {code:"IXE",city:"Mangaluru",   name:"Mangaluru International Airport",               state:"Karnataka",         dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"GOX",city:"Mopa",        name:"Manohar International Airport (North Goa)",     state:"Goa",               dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?w=1400&q=80"},
  {code:"BOM",city:"Mumbai",      name:"Chhatrapati Shivaji Maharaj International Airport",state:"Maharashtra",    dom:70, intl:85, porter:15,buggy:15, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"MYQ",city:"Mysuru",      name:"Mysore Airport",                                state:"Karnataka",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"NAG",city:"Nagpur",      name:"Dr. Babasaheb Ambedkar International Airport",  state:"Maharashtra",       dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"ISK",city:"Nashik",      name:"Nashik Airport",                                state:"Maharashtra",       dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"PAT",city:"Patna",       name:"Jayprakash Narayan International Airport",      state:"Bihar",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"PBD",city:"Porbandar",   name:"Porbandar Airport",                             state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"IXZ",city:"Port Blair",  name:"Veer Savarkar International Airport",           state:"Andaman & Nicobar", dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1400&q=80"},
  {code:"IXD",city:"Prayagraj",   name:"Prayagraj Airport",                             state:"Uttar Pradesh",     dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"PNQ",city:"Pune",        name:"Pune International Airport",                    state:"Maharashtra",       dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1400&q=80"},
  {code:"RPR",city:"Raipur",      name:"Swami Vivekananda Airport",                     state:"Chhattisgarh",      dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"RJA",city:"Rajahmundry", name:"Rajahmundry Airport",                           state:"Andhra Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"RAJ",city:"Rajkot",      name:"Rajkot Airport",                                state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"IXR",city:"Ranchi",      name:"Birsa Munda Airport",                           state:"Jharkhand",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"SXV",city:"Salem",       name:"Salem Airport",                                 state:"Tamil Nadu",        dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1400&q=80"},
  {code:"SHL",city:"Shillong",    name:"Shillong Airport",                              state:"Meghalaya",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1490730141103-6cac27aaab94?w=1400&q=80"},
  {code:"SLV",city:"Shimla",      name:"Shimla Airport",                                state:"Himachal Pradesh",  dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"RQY",city:"Shivamogga",  name:"Kuvempu Airport",                               state:"Karnataka",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=1400&q=80"},
  {code:"IXS",city:"Silchar",     name:"Silchar Airport",                               state:"Assam",             dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1400&q=80"},
  {code:"SXR",city:"Srinagar",    name:"Sheikh ul-Alam International Airport",          state:"J&K",               dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1400&q=80"},
  {code:"STV",city:"Surat",       name:"Surat International Airport",                   state:"Gujarat",           dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"TRV",city:"Thiruvananthapuram",name:"Trivandrum International Airport",        state:"Kerala",            dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?w=1400&q=80"},
  {code:"TRZ",city:"Tiruchirappalli",name:"Tiruchirappalli International Airport",      state:"Tamil Nadu",        dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1400&q=80"},
  {code:"TIR",city:"Tirupati",    name:"Tirupati Airport",                              state:"Andhra Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"TCR",city:"Tuticorin",   name:"Tuticorin Airport",                             state:"Tamil Nadu",        dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1400&q=80"},
  {code:"UDR",city:"Udaipur",     name:"Maharana Pratap Airport",                       state:"Rajasthan",         dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"BDQ",city:"Vadodara",    name:"Vadodara Airport",                              state:"Gujarat",           dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1596422846543-75c6fc197f11?w=1400&q=80"},
  {code:"VNS",city:"Varanasi",    name:"Lal Bahadur Shastri International Airport",     state:"Uttar Pradesh",     dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1400&q=80"},
  {code:"VGA",city:"Vijayawada",  name:"Vijayawada International Airport",              state:"Andhra Pradesh",    dom:70, intl:null,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"},
  {code:"VTZ",city:"Visakhapatnam",name:"Visakhapatnam International Airport",          state:"Andhra Pradesh",    dom:70, intl:85,  porter:15,buggy:null, img:"https://images.unsplash.com/photo-1572461226339-b8b5d91aa931?w=1400&q=80"}
];

// ── Price Override Loader ──────────────────────────────────────
// Admin can update prices via pricing.html → saved to localStorage
// This block applies any saved overrides to the AIRPORTS array at runtime
(function applyPriceOverrides() {
  try {
    var raw = localStorage.getItem('ag_price_overrides');
    if (!raw) return;
    var overrides = JSON.parse(raw);
    if (!overrides || typeof overrides !== 'object') return;
    AIRPORTS.forEach(function(ap) {
      if (overrides[ap.code]) {
        var ov = overrides[ap.code];
        if (ov.dom   !== undefined && ov.dom   !== null) ap.dom    = Number(ov.dom)    || ap.dom;
        if (ov.intl  !== undefined)                      ap.intl   = ov.intl  !== null ? (Number(ov.intl)  || null) : null;
        if (ov.porter !== undefined && ov.porter !== null) ap.porter = Number(ov.porter) || ap.porter;
        if (ov.buggy  !== undefined)                     ap.buggy  = ov.buggy  !== null ? (Number(ov.buggy) || null) : null;
      }
    });
  } catch(e) { /* silent fail — fall back to hardcoded prices */ }
})();
