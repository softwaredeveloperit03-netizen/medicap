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

  selectedReport = [];
  total = 0;
  damages = [];
   constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessDamages();
   }

 
  getInprocessDamages() {
    this.service.get('store/raw.php?type=getInprocessDamages').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.damages =[];
    this.selectedReport = this.results[index];
    this.isView = true;
   // this.total = +this.selectedReport['receiving_details'].outer_damage ;
    this.damage_details = this.selectedReport['damage_details'] ;
    
  }



  viewFile(url) {
    url = this.service.url + '../../upload/damage/' + url +'?v=1';
   window.open(url, '_blank');
 }





  damage_details =[];

  save(data) {
    
    let temp = {};
     
    this.service.post('store/raw.php?type=ApproveDamageInspection&id=' + this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Damage Container Inspection form send for QA Approval');
        this.isView = false;
        this.getInprocessDamages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

 // ApproveDamageInspection
 
   

}
