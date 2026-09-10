import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormBuilder } from '@angular/forms';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
 
 
   
  constructor(private service: DataAccessService,private router: Router) { }
  

  ngOnInit() {
    this.getDepartments();
  }

  replacementManP = 'YES';
 
  departments: any[] = [];
  designations: any[] = [];
  designation = '';

  private readonly ALLOWED_DEPARTMENTS = [
    'Administration',
    'Product Development',
    'Quality Assurance and Compliance',
    'Quality Control',
    'Regulatory Affairs',
    'Material Management',
    'Business Development',
    'Production',
    'Facilities',
    'IT',
  ];

  getDepartments(){
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe((response: any) => {
      const list = Array.isArray(response) ? response : [];
      this.departments = this.ALLOWED_DEPARTMENTS.map((name) =>
        list.find((d: any) => d?.department_name === name) || { id: '', department_name: name, designations: [] }
      );
    });
  }

  getDesignation1() {
    this.designations = [];
    this.designation = '';
    if (!this.department || String(this.department).trim() === '') {
      return;
    }
    this.service
      .get('hr/employee.php?type=getDesignationsByDepartment&department_name=' + encodeURIComponent(this.department))
      .subscribe((response: any) => {
        this.designations = Array.isArray(response) ? response : [];
        if (this.designations.length === 0 && Array.isArray(this.departments)) {
          const dept = this.departments.find((d: any) => d.department_name === this.department);
          this.designations = dept && Array.isArray(dept.designations) ? dept.designations : [];
        }
        if (this.designations.length === 0) {
          alertify.warning('No positions for this department. Add in HR > Masters > Position (select same department).');
        }
      });
    this.getEmployeesbyDept();
  }

 
 

  saveForm(data) {

    if (!data.valid) {
      alertify.error('All fields are mandatory');
      return;
    }
    let temp = data.value;
    temp['expRequired'] = temp['expYears'] +' '+temp['expMonths'];

    this.service.post('hr/manpower.php?type=saveRequisition', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        data.resetForm();
        alertify.success('Manpower Requisition Form been Saved successfully');
        this.router.navigate(['/hr/recruitment/requisition']);
      } else {
        alertify.error(response['status']);
      }
      });
  }
  
  employees1;
  department;
  getEmployeesbyDept( ) {
    this.service.get('hr/employee.php?type=getDepartmentEmployees1' + '&department_name=' + this.department)
    .subscribe(response => {
      this.employees1 = response;
    });
  }

 
  
monthLabels: string[] = ["0 Months", "1 Month", "2 Months", "3 Months", "4 Months", "5 Months", "6 Months", "7 Months", "8 Months", "9 Months", "10 Months", "11 Months"];



yearsArray: string[] = [
  "0 Years", "1 Year", "2 Years", "3 Years", "4 Years", "5 Years",
  "6 Years", "7 Years", "8 Years", "9 Years", "10 Years",
  "11 Years", "12 Years", "13 Years", "14 Years", "15 Years",
  "16 Years", "17 Years", "18 Years", "19 Years", "20 Years",
  "21 Years", "22 Years", "23 Years", "24 Years", "25 Years",
  "26 Years", "27 Years", "28 Years", "29 Years", "30 Years",
  "31 Years", "32 Years", "33 Years", "34 Years", "35 Years",
  "36 Years", "37 Years", "38 Years", "39 Years", "40 Years",
  "41 Years", "42 Years", "43 Years", "44 Years", "45 Years"
];























 
}
