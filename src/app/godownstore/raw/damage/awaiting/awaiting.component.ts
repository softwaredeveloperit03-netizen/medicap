import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  total = 0;
  damages = [];
  checkPointData ;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDamages();
    this.getCheckPointData1();
  }




  damaeImg1: File;
  damaeImg2: File;
 
  onFileChanged1(event) {
    this.damaeImg1 = event.target.files[0];
  }

  onFileChanged2(event) {
    this.damaeImg2 = event.target.files[0];
  }




  
  getCheckPointData1(){
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form=Damage').subscribe(response => {
     this.checkPointData = response;
   });
 }

  getPendingDamages() {
    this.service.get('store/raw.php?type=getPendingDamages').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.damages =[];
    this.selectedReport = this.results[index];
    this.isView = true;
    this.total = +this.selectedReport['receiving_details'].outer_damage ;
    
    for (let i = 0; i < +this.selectedReport['receiving_details'].outer_damage; i++) {
      let temp = {};
      temp['container_no'] = i+1;
      temp["status"] = "Outer Damage";
      temp['remark'] = "";
      this.damages[this.damages.length] = temp;
    }
  }

  save(data) {
    if(!data.valid){
      alertify.error('All Field Required !!!!!');
      return;
    }
    const uploadData = new FormData();
   
    if (this.damaeImg1 !== undefined) {
      uploadData.append('damaeImg1', this.damaeImg1, this.damaeImg1.name);
    } 

    if (this.damaeImg2 !== undefined) {
      uploadData.append('damaeImg2', this.damaeImg2, this.damaeImg2.name);
    } 

    uploadData.append('total_damage', this.total+'');
    uploadData.append('containers', JSON.stringify(this.damages));
    uploadData.append('checkPointData', JSON.stringify(this.checkPointData));

    this.service.post('store/raw.php?type=saveDamageInspection&id=' + this.selectedReport['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Damage Container Inspection form send for QA Approval');
        this.isView = false;
        this.getPendingDamages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
