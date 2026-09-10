import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-prevenapproval',
  templateUrl: './prevenapproval.component.html',
  styleUrls: ['./prevenapproval.component.css']
})
export class PrevenapprovalComponent implements OnInit {

  isView = false;
  equipmentslog;

 selectedResult = [];

   
 constructor(private service:DataAccessService) { }

  ngOnInit() {
     this.getMaintenanceData();
  }

  
  getMaintenanceData() {
    
   this.service.get('engineering/preventive.php?type=getPmForChecking&status=Checked&due_type=Preventive').subscribe(response => {
     this.equipmentslog = response;
    });
 }
 
 view1(index){
   this.selectedResult=this.filteredMaterials[index];
    this.isView = true;
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





 mpin ='';
 emp_id = '';
 isDIGI = false;
 isbutton = true;
 status;
 openDigiSign(status){
   this.emp_id = localStorage.getItem('emp_id');
   this.status=status;
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
        this.Change_status(this.status);
     } else {
       alertify.error('Digi-Sign Not Verified');
     }
   });
 }







 Change_status(status) {

  let temp ={};
 
  this.service.post('engineering/preventive.php?type=ChangeStatusOfPMApproval&status='+status+'&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      this.isView = false;
      alertify.success(this.service.t('common.savedSuccess'));
      this.getMaintenanceData();
     } else {
      alertify.error('Failed:' + result.status);
    }
  });
 
 }





}
