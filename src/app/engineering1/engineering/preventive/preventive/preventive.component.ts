import { Component, OnInit } from '@angular/core';
import { CalendarView } from 'angular-calendar';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preventive',
  templateUrl: './preventive.component.html',
  styleUrls: ['./preventive.component.css']
})
export class PreventiveComponent implements OnInit {

  isLast = false;
  isView = false;
  isIntimation = false;
  equipmentslog;
  hide=true;

 selectedResult = [];

 frequencies = [
   { name: 'Daily' },
   { name: 'Weekly' },
   { name: 'Monthly' },
   { name: 'Quaterly' },
   { name: 'Annually' },
 ];


 calibration_type='';

 currentDate: Date;
 displayMonth: string;






 constructor(private service:DataAccessService) {
  this.loggedInDept = localStorage.getItem('department');

  }

 ngOnInit() {
  this.get_rights(); 
 this.currentDate = new Date();
 this.updateDisplayMonth(this.currentDate);
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



 printDiv(): void {
  this.isView = false;

  // Clone the document body
  let printContents = document.getElementById('divToPrint').innerHTML;
  let originalContents = document.body.innerHTML;
  document.body.innerHTML = printContents;

  // Print the document
  window.print();

  // Restore the original document content
  document.body.innerHTML = originalContents;

  // Reload the page after printing
  window.addEventListener('afterprint', () => {
    window.location.reload();
  });
}


 previousMonth() {
   this.currentDate.setMonth(this.currentDate.getMonth() - 1);
   this.updateDisplayMonth(this.currentDate);
 }

 currentMonth() {
   this.currentDate = new Date();
   this.updateDisplayMonth(this.currentDate);
 }

 nextMonth() {
   this.currentDate.setMonth(this.currentDate.getMonth() + 1);
   this.updateDisplayMonth(this.currentDate);
 }

 updateDisplayMonth(date: Date) {
   const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
   const month = date.getMonth();
   const year = date.getFullYear();
   this.displayMonth = `${monthNames[month]} ${year}`;

     this.getMaintenanceData(month,year);
 }




 


  getMaintenanceData(month,year) {
    month =month + 1;
   this.service.get('engineering/preventive.php?type=get_monthly_schedule&month=' + month + '&year=' + year + '&due_type=Preventive').subscribe(response => {
     this.equipmentslog = response;
    });
 }


 checkPointData;



 view(index){
   this.selectedResult=this.filteredMaterials[index];
    this.isLast = true;
    this.checkPointData =[];
  
    this.service.get('engineering/preventive.php?type=getchecklistData&frequency=' + this.selectedResult['frequency'] + '&equipment_id=' + this.selectedResult['equipment_code'] + '&frequency_type=Preventive').subscribe(response => {
      this.checkPointData = response;
     });
 }
 view1(index){
   this.selectedResult=this.filteredMaterials[index];
    this.isView = true;
   
   
 }
 intimation_data =[];

 view2(index){
  this.intimation_data =[];
   this.selectedResult=this.filteredMaterials[index];
   this.intimation_data = this.selectedResult['intimation_data'];
    this.isIntimation = true;
 }




 mpin ='';
 emp_id = '';
 isDIGI = false;
 isbutton = true;
 formData;
 openDigiSign(formData){
   this.emp_id = localStorage.getItem('emp_id');
   this.formData=formData;
   this.isDIGI = true;
 }

 loginPassward ='';
 digiSign(data){

   if (!data.valid) {
     alert('Passward OR Login PIN Required!!!!');
     return;
   }

   this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
     if (response['status'] == 'success') {
       alertify.success('Digi-Sign Verified successfully');
       this.isDIGI = false;
       this.isbutton = false;
       this.loginPassward ='';
        this.editEquipment(this.formData);
     } else {
       alertify.error('Digi-Sign Not Verified');
     }
   });
 }


 


 

 searchQuery;


 get filteredMaterials(): any[] {
   if (!this.searchQuery || this.searchQuery.trim() === '') {
     return this.equipmentslog; // If search query is empty or whitespace, return all materials
   }
   
   const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
   return this.equipmentslog.filter(material => {
     // Check if any field of the material contains the search query
     return Object.entries(material).some(([key, value]) => {
          return value && value.toString().toLowerCase().includes(query);
       
     });
   });
 }



  
 editEquipment(formData) {

  if (!formData.valid) {
    alertify.error('All fields are required!');
    return;
  }

  let temp = formData.value;
  temp['checklist'] = this.checkPointData;
  this.service.post('engineering/preventive.php?type=saveChecklist_data&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isLast = false;
      this.updateDisplayMonth(this.currentDate);
      this.isbutton = true;
      alertify.success(this.service.t('common.savedSuccess'));
      formData.reset();
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }

 sendIntimation(formData) {
console.log("IN");

  if (!formData.valid) {
    alertify.error('All fields are required!');
    return;
  }

 
  let temp = formData.value;

  this.intimation_data.push(temp);

 
  this.service.post('engineering/preventive.php?type=saveIntimation&intimation_status=Inprocess&id=' + this.selectedResult['id'], JSON.stringify(  this.intimation_data)).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isIntimation = false;
      this.updateDisplayMonth(this.currentDate);
       alertify.success('Send Intimation Successfully');
      formData.reset();
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }


 AcceptIntimation( ) {

  // if (!formData.valid) {
  //   alertify.error('All fields are required!');
  //   return;
  // }

  let temp = {};

 
  this.service.post('engineering/preventive.php?type=acceptIntimation&intimation_status=Accepted&id=' + this.selectedResult['id'], JSON.stringify( temp)).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isIntimation = false;
      this.updateDisplayMonth(this.currentDate);
       alertify.success('Send Intimation Successfully');
       
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }





}
