import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checkshift',
  templateUrl: './checkshift.component.html',
  styleUrls: ['./checkshift.component.css']
})
export class CheckshiftComponent implements OnInit {

  selectedResult:any
  results;
  constructor(private service: DataAccessService) { }

  operator_category = 'Staff';


  departments;
  ngOnInit() {
    this.getShiftSchedule();
    this.getDepartments();
  }

  department_name = 'Production';
  selDate;

  getShiftSchedule() {
    this.service.get('hr/shift.php?type=getAbsentShiftScheduleLog&department_name='+this.department_name+'&selDate='+this.selDate).subscribe(response => {
      this.results = response;
    });
  }


  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }

  
  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.results.filter(material => {
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
    const headers = ['Sr. No.', 'Employee Category', 'Emp Id', 'Emp Name', 'Shift', 'From Date', 'To Date'];
    csvContent += headers.join(',') + '\n';

    // Add row data
    this.filteredMaterials.forEach((item, index) => {
      const row = [
        index + 1,
        item.operator_category || 'NA',
        item.Empolyee_Id || 'NA',
        `${item.firstname} ${item.lastname}` || 'NA',
        item.shift_name || 'NA',
        this.formatDate(item.Start_date) || 'NA',
        this.formatDate(item.End_date) || 'NA'
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
