import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx/xlsx.mjs';
declare let alertify;
@Component({
  selector: 'app-misspunch',
  templateUrl: './misspunch.component.html',
  styleUrls: ['./misspunch.component.css']
})
export class MisspunchComponent implements OnInit {
  month;
  employees;
  from_date = '';
  to_date = '';
  searchQuery;
  constructor(private service: DataAccessService, private router: Router) {
    var d = new Date();
    let day = d.getDate();
    let m = d.getMonth();
    m = +m + 1;
    let mon = "";
    if (day > 0 && day < 10) {
      mon = "0" + day;
    }
    if (m > 0 && m < 10) {
      mon = "0" + m;
    }
    this.to_date = d.getFullYear() + "-" + mon + "-" + day;
    this.from_date = d.getFullYear() + "-" + mon + "-01";
  }
  

  ngOnInit(): void {
    // this.getDepartmentsEmployee();
  }
  
  
  get_data(value){
    // this.getDepartmentsEmployee(value)
    console.log('selected Month:=>',this.month)
  }


  // getDepartmentsEmployee(value) {
  //   this.service.get('hr/attendance.php?type=misspunch&date='+value).subscribe(response => {
  //     this.employees = response;
  //   });
  // }

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
  getMissPunchAttendance() {
    if(this.month && this.from_date &&  this.to_date ) {
      this.service.get('hr/attendance.php?type=misspunch&date='+this.month + '&from_date='+ this.from_date + '&to_date='+ this.to_date).subscribe(response => {
        this.employees = response;
      });
    } else {
      alertify.error('Please select all fields to search')
    }
   
  }
  exportToExcel(): void {
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(this.employees.map(employee => ({
      'Employee ID': employee.emp_id,
      'Employee Name': `${employee.firstname} ${employee.lastname}`,
      'Department': employee.department,
      'Designation': employee.designation,
      'Date': this.formatDate(employee.indate),
      'Missed Punch': (employee.intime === ' ' ? 'Intime Punch' : '') + (employee.outtime === ' ' ? ' Outtime Punch' : '')
    })));
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };
    XLSX.writeFile(workbook, 'employees.xlsx');
  }
  formatDate(dateString: string): string {
    const [year, month, day] = dateString.split('-');
    return `${day}-${month}-${year}`;
  }
}
