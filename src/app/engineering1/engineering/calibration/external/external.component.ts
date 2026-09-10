import { Component, OnInit } from '@angular/core';
import { CalendarView } from 'angular-calendar';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-external',
  templateUrl: './external.component.html',
  styleUrls: ['./external.component.css']
})
export class ExternalComponent implements OnInit {
  isView = false;
  isView1 = false;
  equipmentslog;

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
   this.service.get('engineering/calibration.php?type=get_monthly_schedule&month=' + month + '&year=' + year + '&due_type=External').subscribe(response => {
     this.equipmentslog = response;
    });
 }




 view(index){
   this.selectedResult=this.filteredMaterials[index];
    this.isView = true;
 }
 view1(index){
   this.selectedResult=this.filteredMaterials[index];
    this.isView1 = true;
 }

 structureFile: File;


  onFileChanged(event, id) {
    if (event.target.files.length === 1) {
        this.structureFile = event.target.files[0];
    }
  }

  viewPhoto(url) {
    window.open(this.service.url + '../../upload/calibration/' + this.selectedResult['report_file']);
    window.open(url, '_blank');
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


 calibration_date='';
 agency_name ='';

  
 editEquipment(data) {
 

  if (!data.valid) {
    alertify.error('Please Enter required Field');
    return;
  }


  const uploadData = new FormData();

  if (this.structureFile !== undefined) {
    uploadData.append('structure_file', this.structureFile, this.structureFile.name);
  }

  uploadData.append('agency_name', this.agency_name );
  uploadData.append('calibration_date', this.calibration_date);



  this.service.post('engineering/calibration.php?type=saveExternalCalibration&id='+this.selectedResult['id'], uploadData).subscribe(response => {
    const result = JSON.parse(JSON.stringify(response));
    if (result.status === 'success') {
      alertify.success(this.service.t('common.savedSuccess'));
      this.updateDisplayMonth(this.currentDate);
      this.isView = false;
      data.reset();
    } else {
      alertify.error('Failed:' + result.status);
    }
  });
 }





}
