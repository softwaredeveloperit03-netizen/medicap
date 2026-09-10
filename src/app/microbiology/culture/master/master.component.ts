import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css'],
})
export class MasterComponent implements OnInit {
  isNew = false;

  results: any = [
    {
      'Name of organism': 'Bacillus subtilis',
      'ATCC No.': 'ATCC 6633',
      'Macroscopic Features':
        'Large, irregular, flat, undulate edge, gray and wrinkled appearance.',
      'Microscopic Features (Gram staining)': 'Gram positive straight rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'S. aureus',
      'ATCC No.': 'ATCC 6538',
      'Macroscopic Features':
        'Medium to large, convex, circular, glistening, creamy, opaque, light and dark gold colonies.',
      'Microscopic Features (Gram staining)': 'Gram positive cocci',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Escherichia coli',
      'ATCC No.': 'ATCC 8739',
      'Macroscopic Features': 'Medium to large, gray, Mucoid, convex.',
      'Microscopic Features (Gram staining)': 'Gram negative straight rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Salmonella abony',
      'ATCC No.': 'NCTC 6017',
      'Macroscopic Features': 'Medium, gray/white, circular, convex colonies',
      'Microscopic Features (Gram staining)': 'Gram negative straight rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Shigella boydii',
      'ATCC No.': 'ATCC 8700',
      'Macroscopic Features':
        'Colonies are small in diameter, circular, convex, smooth and transparent',
      'Microscopic Features (Gram staining)': 'Gram-negative rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Ps. aeruginosa',
      'ATCC No.': 'ATCC 9027',
      'Macroscopic Features':
        'Large, flat, circular to irregular shaped, gray with silver sheen.          A second type may also be small, round, shiny colonies.',
      'Microscopic Features (Gram staining)':
        'Gram negative straight or slightly curved rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Serratia marcescens',
      'ATCC No.': 'ATCC 14756',
      'Macroscopic Features':
        'Colonies are round glabrous,convex and slightly umbonate with entire margins and opaque ',
      'Microscopic Features (Gram staining)': 'Gram negative straight rods',
      'Maintenance medium': 'Soyabean casein digest agar (SCDA) ',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Clostridium Sporogenes',
      'ATCC No.': 'ATCC 19404',
      'Macroscopic Features':
        'Large, Irregularly circular, with coarse rhizoid edge, raised yellowish gray centre and flattened periphery.',
      'Microscopic Features (Gram staining)': 'Gram positive straight rods',
      'Maintenance medium': 'Columbia Agar (COA)',
      'Temperature / Incubation Time':
        '30°-35°C for 18-24 hours in Aerobic Condition',
    },
    {
      'Name of organism': 'Aspergillus brasciliensis',
      'ATCC No.': 'ATCC 16404',
      'Macroscopic Features':
        'Initially white or pale yellow colonies become black with spore production. Reverse is pale yellow.',
      'Microscopic Features (Gram staining)': 'Filamentous Structure ',
      'Maintenance medium': 'Sabouraud dextrose agar (SDA)',
      'Temperature / Incubation Time':
        '20-25°C for 3 to 5 days in Aerobic Condition',
    },
    {
      'Name of organism': 'Candida  albicans',
      'ATCC No.': 'ATCC 10231',
      'Macroscopic Features':
        'Small to Medium, white, circular, convex, dull colonies',
      'Microscopic Features (Gram staining)':
        'Gram positive, ovoidal, budding yeast cells.',
      'Maintenance medium': 'Sabouraud dextrose agar (SDA)',
      'Temperature / Incubation Time':
        '20-25°C for 3 to 5 days in Aerobic Condition',
    },
  ];

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
  }
  selectedCulture: any = {}; // To store the selected culture details

  getCultureDetails(selectedOrganism: string) {
    // Find the culture details based on the selected organism name
    this.selectedCulture =
      this.results.find(
        (culture) => culture['Name of organism'] === selectedOrganism
      ) || {};
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
}
