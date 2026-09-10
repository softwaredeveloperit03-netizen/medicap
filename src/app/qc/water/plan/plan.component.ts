
// import { Component, OnInit } from '@angular/core';
// import { DataAccessService } from 'src/app/data-access.service';

// @Component({
//   selector: 'app-plan',
//   templateUrl: './plan.component.html',
//   styleUrls: ['./plan.component.css']
// })
// export class PlanComponent implements OnInit {
//   results;

//   keys;
//   constructor( private service:DataAccessService) { }

//   ngOnInit(): void {
//     this.getSamplingPlan()
//   }
//   getSamplingPlan(){
//     this.service.get('qc/water.php?type=getSamplingPlan').subscribe(response=>{
//       this.results=response;
//       let temp = this.results[0];
//       this.keys = Object.keys(temp);

//       for (let i = 0; i < this.keys.length; i++) {
//         let key = this.keys[i];
//         if (key == 'point_no') {
//           this.keys.splice(i, 1);
//         }
//         if (key == 'point_name') {
//           this.keys.splice(i, 1);
//         }
//       }

//       for (let i = 0; i < this.keys.length; i++) {
//         let key = this.keys[i];
//         if (key == 'point_name') {
//           this.keys.splice(i, 1);
//         }
//       }
//     })
//   }
//   download(){
//     this.service.open('qc/water.php?type=downloadSamplingPlan')
//   }
// }


import { Component, OnInit } from '@angular/core';
import { CalendarView } from 'angular-calendar';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-plan',
  templateUrl: './plan.component.html',
  styleUrls: ['./plan.component.css']
})
export class PlanComponent implements OnInit {

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






 constructor(private service:DataAccessService) { }

 ngOnInit() {
     
 this.currentDate = new Date();
 this.updateDisplayMonth(this.currentDate);
 }

 printDiv(): void {
  this.download();
}

 download() {
   const month = this.currentDate.getMonth() + 1;
   const year = this.currentDate.getFullYear();
   this.service.open('qc/water.php?type=downloadMonthlySchedule&month=' + month + '&year=' + year + '&due_type=Frequency');
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
   this.service.get('qc/water.php?type=get_monthly_schedule&month=' + month + '&year=' + year + '&due_type=Frequency').subscribe(response => {
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
  sendingScheduleId: number | null = null;
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
      alertify.success('Saved Successfully');
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

sendForSampling(schedule: any) {
  if (!this.isSamplingWindowOpen(schedule)) {
    return;
  }

  this.sendingScheduleId = Number(schedule.id);
  this.service
    .get('qc/water.php?type=sendForSampling&id=' + schedule.id)
    .subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          alertify.success('Entry sent for sampling allocation');
          this.updateDisplayMonth(this.currentDate);
        } else {
          alertify.error('Failed to send for sampling');
        }
        this.sendingScheduleId = null;
      },
      () => {
        alertify.error('Failed to send for sampling');
        this.sendingScheduleId = null;
      }
    );
}

isSamplingWindowOpen(schedule: any): boolean {
  const remaining = Number(schedule?.remaining_days);
  const samplingStatus = (schedule?.sampling_status || '').toString().toLowerCase();
  const scheduleStatus = (schedule?.status || '').toString().toLowerCase();

  if (Number.isNaN(remaining)) return false;
  if (scheduleStatus !== 'pending') return false;
  if (samplingStatus && samplingStatus !== 'pending') return false;

  return remaining >= 0 && remaining <= 2;
}

isSamplingSent(schedule: any): boolean {
  const samplingStatus = (schedule?.sampling_status || '').toString().toLowerCase();
  return samplingStatus !== '' && samplingStatus !== 'pending';
}





}
