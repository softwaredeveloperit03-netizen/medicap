import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
declare let alertify;
@Component({
  selector: 'app-additional',
  templateUrl: './additional.component.html',
  styleUrls: ['./additional.component.css']
})
export class AdditionalComponent implements OnInit {

  
  results;
  departments;
  search_department ='ALLEMP';
   
  
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getRights();
    this.getDepartments();
    this.search_department ='ALLEMP';
   
   }
  
  getRights(){
    if(this.search_department==''|| this.search_department==null)
      {
    this.search_department ='ALLEMP';
      }
    this.service.get('hr/employee.php?type=getRights_Log&department_name=' + this.search_department +'&employee_type=Technical').subscribe(response => {
      this.results = response;

     });
  }
 
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  exportToExcel(): void {
    // Map the data for the Excel file
    const formattedData = this.results.map(user => ({
      'Emp Id': user.emp_id,
      'Name': `${user.firstname} ${user.lastname}`,
      'Department': user.department,
      'Designation': user.designation,
      'User Rights': user.isuser,
      'Checker Rights': user.ischecker,
      'Approver Rights': user.isapprover,
      'QMS Approver': user.qms_approver,
      'Auditor': user.isauditor,
      'Dept Head': user.dept_head,
      'Shift Management': user.shift_allocator
    }));

    // Create a worksheet
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);

    // Create a workbook and add the worksheet
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };

    // Save the workbook to a file
    XLSX.writeFile(workbook, 'UserRights.xlsx');
  }
    
  adddepartments;
  getaddDep_data() {
    this.service.get('hr/employee.php?type=getaddDep_data&emp_id1='+this.emp_id1).subscribe(response => {
      this.adddepartments = response;
      console.log('this.adddepartments :>> ', this.adddepartments);
    });
  }



  isAllocation=false;
  selctedEmp=[];
  emp_id1;
  emp_id;
  designation;
  firstname;
lastname;
department;




isuser='No';
ischecker='No';
isapprover='No';
qms_approver='No';
trainig_cordinator='No';
isauditor='No';
dept_head='No';
shift_allocator='No';
  addDep(index){
    this.isAllocation=true;
    this.selctedEmp=this.results[index]
    this.emp_id1=this.selctedEmp['emp_id'];
    this.emp_id=this.selctedEmp['emp_id'];
    this.firstname=this.selctedEmp['firstname'];
    this.lastname=this.selctedEmp['lastname'];
    this.designation=this.selctedEmp['designation'];
    this.getaddDep_data();
  }




  allocateRights(data){
      let temp=data.value;
      temp['emp_id']=this.selctedEmp['emp_id'];

      console.log('temp :>> ', temp);

      this.service.post('hr/employee.php?type=update_additional_empRights',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Data Updated Successfully!');
          this.isuser='No';
          this.ischecker='No';
          this.isapprover='No';
          this.qms_approver='No';
          this.trainig_cordinator='No';
          this.isauditor='No';
          this.dept_head='No';
          this.shift_allocator='No';
          this.getRights();
          this.getaddDep_data()
        } else {
          alertify.error('Failed, an error occurred. Please try again!');
        }
      });

  }
}
