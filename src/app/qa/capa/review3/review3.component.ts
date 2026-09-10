import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review3',
  templateUrl: './review3.component.html',
  styleUrls: ['./review3.component.css']
})
export class Review3Component implements OnInit {

  
  isView = false;
  results;
  review3_comment='';

  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];
  plant_id:any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
    this.plant_id = this.service.getPlantConfigFields('plant_id');

  }

  getInprocessIncidents(){
    this.service.get('qms/capa.php?type=getReview3CAPA').subscribe(response => {
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
    this.service.get('qms/capa.php?type=saveReview3&capa_no='+this.selectedDev['capa_no']+'&id='+this.selectedDev['id']+'&review3_comment='+this.review3_comment).subscribe(response => {
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
