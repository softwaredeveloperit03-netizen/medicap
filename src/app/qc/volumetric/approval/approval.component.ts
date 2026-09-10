import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;
   constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVolumetricPreparation();
  
  }
  selectedResult =[];

  
 

  employee;

  getVolumetricPreparation() {
    this.service.get('qc/volumetric.php?type=getVolumetricPreparationForApproval').subscribe(response => {
      this.results = response;
    });
  }
  
   
  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  viewPhoto(url) {
    window.open(this.service.url + '../../upload/volumetric/' + this.selectedResult['weigh_slip']);
     window.open(url, '_blank');
  }



   
  saveVolumetricPreparation() {
    
    let temp ={};

    temp['chemicals'] = this.selectedResult['chemicals'];
    temp['Reagent'] = this.selectedResult['Reagent'];

    
    this.service.post('qc/volumetric.php?type=approveVolSol&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Approved Successfully');
        this.isView=false;
        this.getVolumetricPreparation();
       } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }








 
  
}





