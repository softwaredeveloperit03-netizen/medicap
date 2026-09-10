import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  holidays;
  isNewHoliday = false;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  // Function to calculate the starting Sr.No based on the current page and page size
  // calculateStartSrNo(): number {
  //   return (this.currentPage - 1) * this.pageSize +1;
  // }
  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10;
  }

  // Update the current page when the page changes
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }

  // Handle the change event for the page size dropdown
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf() {
    //  this.isView=false;
    //  this.getLogs();
    this.currentPage = 1;
    this.pageSize = 10;
  }
  // ---------------------------------------------------------------------//
  ngOnInit() {
    this.getHolidays();
    this.get_rights();
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
  getHolidays() {
    this.service
      .get('hr/holiday.php?type=get_holidays_list')
      .subscribe((response) => {
        this.holidays = response;
      });
  }

  addHolidays(qualificationForm) {
    this.service
      .post(
        'hr/holiday.php?type=save_holidays',
        JSON.stringify(qualificationForm.value)
      )
      .subscribe(
        (response) => {
          if (response['status'] == 'success') {
            qualificationForm.reset();
            this.getHolidays();
            this.isNewHoliday = false;
            alertify.success('save successfully');
          } else {
            alertify.error(response['status']);
          }
        },
        (error: Response) => {
          if (error.status === 400) {
            alertify.error('An error has occurred.');
          } else {
            alertify.error(
              'An error has occurred, http status:' + error.status
            );
          }
        }
      );
  }

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.holidays; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.holidays.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  isPastDate(date: string): boolean {
    const currentDate = new Date();
    const holidayDate = new Date(date);
    return holidayDate < currentDate;
  }

  exportToExcel(): void {
    const rows = Array.isArray(this.filteredMaterials) ? this.filteredMaterials : [];
    if (rows.length === 0) {
      alertify.error('No record to export');
      return;
    }
    const formattedData = rows.map((user, index) => ({
      'Sr. No': index + 1,
      'Holiday Date': this.formatHolidayDate(user.holiday_date),
      'Holiday Name': user.holiday_name || '',
    }));
    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook: XLSX.WorkBook = {
      Sheets: { Holidays: worksheet },
      SheetNames: ['Holidays'],
    };
    XLSX.writeFile(workbook, 'Holidays.xlsx');
  }

  private formatHolidayDate(dateString: string): string {
    if (!dateString) {
      return '';
    }
    const d = new Date(dateString);
    if (isNaN(d.getTime())) {
      return dateString;
    }
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return day + '-' + month + '-' + year;
  }
}
