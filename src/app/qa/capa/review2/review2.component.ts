import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review2',
  templateUrl: './review2.component.html',
  styleUrls: ['./review2.component.css']
})
export class Review2Component implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];
  plant_id:any;

  review2_comment = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
    this.plant_id = this.service.getPlantConfigFields('plant_id');

  }

  getInprocessIncidents(){
    this.service.get('qms/capa.php?type=getReview2CAPA').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  add(data){
    this.capas[this.capas.length]=data.value;
    data.reset();
  }
  del(index){
    this.capas.splice(index,1);

  }
  save() {
    /* let temp=[];
    temp=data.value;
    temp['capa_extension']=this.capas;
    this.service.post('qms/capa.php?type=saveReview2&capa_no='+this.selectedDev['capa_no']+'&id='+this.selectedDev['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessIncidents();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    }); */

    this.service.get('qms/capa.php?type=saveReview2&capa_no='+this.selectedDev['capa_no']+'&id='+this.selectedDev['id'] + '&review2_comment=' + this.review2_comment).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessIncidents();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
