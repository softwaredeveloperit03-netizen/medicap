import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-resigned',
  templateUrl: './resigned.component.html',
  styleUrls: ['./resigned.component.css']
})
export class ResignedComponent implements OnInit {
  
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getResignedEmployeeList();
    this.getDepartments();
  }
 
 
  departments;
  getDepartments(){
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe(response => {
      this.departments = response;
    });
  }
   

  designations=[]
  department = '';
  designation = '';

  getDesignation1() {
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == this.department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }



  activeEmployees;
  getActiveEmpBasedOnDeptOrDesignation() {
    this.service.get('hr/employee.php?type=getActiveEmpBasedOnDeptOrDesignation&empDept=' + this.department + '&empDesig=' + this.designation).subscribe(response => {
      this.activeEmployees = response;
    });
  }
 


  employees;
  getResignedEmployeeList() {
    new Promise((res,rej)=>{
    this.service.get('hr/employee.php?type=getResignedEmployeeList').subscribe(response => {
      this.employees = response;
      res(response);
    });
    })
  }
 
  isView = false;
  selectedResult = [];
 
  view(data) {
    this.selectedResult = [];
    this.selectedResult = data;
    this.isView = true;
  }

  viewDoc(url) {
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }

  isResignation = false;

  exitEmployee(data){

    if(!data.valid){
      alertify.error('All Fields are required!');
      return;
    }
    let temp = data.value;
    this.service.post('hr/employee.php?type=exitEmployee', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Employee exit Successfully');
        this.getResignedEmployeeList();
        this.isResignation = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 
 







    
  searchQuery;
  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.employees; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.employees.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }




   exportToExcel(): void {
    let csvContent = '';

    // Add column headers
    const headers = ['Sr. No.', 'Employee ID','Employee Type', 'First Name', 'Middle Name', 
    'Last Name','Contact No','Email Id','Department','Designation','Employee Category',
    'Joining Status','Gender','Date Of Birth','Joining Date','Employee Level','Aadhar No',
    'Pan No','Highest Qualification','Prev. Experience (In Yrs)','Nationality',
    'Marital Status','Blood Group','Last Date Of Working','Reason For Leaving'];

    csvContent += headers.join(',') + '\n';

    // Add row data
    this.filteredMaterials.forEach((item, index) => {
      const row = [
        index + 1,
        item.emp_id || '-',
        item.employee_type || '-',
        item.firstname || '-',
        item.middlename || '-',
        item.lastname || '-',
        item.contact_no || '-',
        item.emp_email || '-',
        item.department || '-',
        item.designation || '-',
        item.operator_category || '-',
        item.joining_status || '-',
        item.gender || '-',
        this.formatDate(item.birthdate) || '-',
        this.formatDate(item.joining_date) || '-',
        item.emp_level || '-',
        item.adhar || '-',
        item.pan || '-',
        item.qualification || '-',
        item.experience || '-',
        item.nationality || '-',
        item.marital_status || '-',
        item.blood_group || '-',
        item.lastDateOfWorking || '-',
        item.reasonForLeaving || '-'

      ];
      csvContent += row.join(',') + '\n';
    });

    // Create a Blob containing the CSV content
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

    // Create a temporary anchor element to trigger the download
    const link = document.createElement('a');
    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      var name = `shift_schedule_log.csv`
      link.setAttribute('download', 'exported_data.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }



  formatDate(date: string): string {
    // Parse the date string into a Date object
    const parsedDate = new Date(date);
  
    // Extract the day, month, and year components
    const day = parsedDate.getDate();
    const month = parsedDate.getMonth() + 1; // Month is zero-based, so add 1
    const year = parsedDate.getFullYear();
  
    // Pad single-digit day and month with leading zero if necessary
    const formattedDay = day < 10 ? '0' + day : day.toString();
    const formattedMonth = month < 10 ? '0' + month : month.toString();
  
    // Construct the formatted date string in "dd-mm-yyyy" format
    const formattedDate = formattedDay + '-' + formattedMonth + '-' + year;
  
    return formattedDate;
  }






 

  
}
