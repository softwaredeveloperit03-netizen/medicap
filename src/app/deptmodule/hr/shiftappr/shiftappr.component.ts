import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-shiftappr',
  templateUrl: './shiftappr.component.html',
  styleUrls: ['./shiftappr.component.css']
})
export class ShiftapprComponent implements OnInit {
  selectedResult:any
   results;
   constructor(private service: DataAccessService) { }
 
   operator_category = 'Staff';
 
 
   ngOnInit() {
     this.getShiftSchedule();
   }
 
   getShiftSchedule() {
     this.service.get('hr/shift.php?type=getShiftSchedule').subscribe(response => {
       this.results = response;
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
 
   Review(i)
   {
     this.service.get('hr/shift.php?type=ReviewShedule&Id='+(i+1)).subscribe(response => {
       if (response['status'] == 'success') {
         // this.router.navigate(['/production/stages-master']);
         alert('updated Successfully')
         this.getShiftSchedule()
       } else {
         alert('Failed: An error occured, please try again!');
       }
     });
 
   }
 
   Approve(id)
   {
 
     this.service.get('hr/shift.php?type=ApproveShedule&Id='+id).subscribe(response => {
       if (response['status'] == 'success') {
         // this.router.navigate(['/production/stages-master']);
         alert('updated  Successfully');
         this.getShiftSchedule()
 
       } else {
         alert('Failed: An error occured, please try again!');
       }
     });
     
   }


   approveAllShift()
   {

    let temp ={};
    temp['data']  = this.filteredMaterials;
 
     this.service.post('hr/shift.php?type=ApproveAllShiftShedule' ,JSON.stringify(temp) ).subscribe(response => {
       if (response['status'] == 'success') {
          alert('All Shift Approved  Successfully');
         this.getShiftSchedule()
 
       } else {
         alert('Failed: An error occured, please try again!');
       }
     });
     
   }
 }
 