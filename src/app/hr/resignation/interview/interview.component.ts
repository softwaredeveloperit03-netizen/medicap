import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-interview',
  templateUrl: './interview.component.html',
  styleUrls: ['./interview.component.css']
})
export class InterviewComponent implements OnInit {
  designations;
  resignations;
  selectedResignation;
  departments;
  isShow = false;
  id;
  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getPendingExitInterview();
    this.getDepartments();
    this.get_rights();
  }
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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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

  department ='';


  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
  employees;
  getEmployees(){
    this.service.get('hr/employee.php?type=get_EMP_by_department&department1='+this.department)
    .subscribe(response => {
      this.employees = response;
    });
  }


  selectedEmp;
  emp_ids;
  designation;
  getEmployees_id(index){
    this.selectedEmp=this.employees[index-1];
    this.emp_ids=this.selectedEmp['emp_id']
    this.designation=this.selectedEmp['designation']
    console.log(this.selectedEmp)
    console.log(this.emp_ids)
  }

 

  getPendingExitInterview() {
    this.service.get('hr/resignation.php?type=getDirectExitEmp')
    .subscribe(response => {
      this.resignations = response;
    });
  }

 
   

  saveExitInterview(data) {
    let temp = data.value;
     
    this.service.post('hr/resignation.php?type=saveDirectExit&emp_id1234='+this.selectedEmp['emp_id'], JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Exit Successfully');
        data.reset();
        this.isShow = false;
        this.getPendingExitInterview();
      } else {
        alert(response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  exportToExcel(): void {
    const formattedData = this.resignations.map((user, index) => ({
      'Sr. No.': index + 1,
      'Emp. Name': `${user.firstname} ${user.lastname}`,
      'Emp ID': user.emp_id,
      'Department': user.department ,
      'Designation': user.designation,
      'Joining Date':  this.formatDate(user.joining_date),
      'Exit Date': this.formatDate(user.resign_date)
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };

    XLSX.writeFile(workbook, 'Data.xlsx');
  }
  formatDate(dateString: string): string {
    const [year, month, day] = dateString.split('-');
    return `${day}-${month}-${year}`;
  }
}
