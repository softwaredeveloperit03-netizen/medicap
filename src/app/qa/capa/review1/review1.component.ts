import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review1',
  templateUrl: './review1.component.html',
  styleUrls: ['./review1.component.css']
})
export class Review1Component implements OnInit {
  isView = false;
  results;

  selectedDev = [];
  remark = '';
  comment='';
  prev_occured='';
  plant_id:any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessIncidents();
    this.plant_id = this.service.getPlantConfigFields('plant_id');

  }

  getInprocessIncidents(){
    this.service.get('qms/capa.php?type=getReview1CAPA').subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  save(data) {
    this.service.post('qms/capa.php?type=saveReview1&capa_no='+this.selectedDev['capa_no']+'&id='+this.selectedDev['id'],JSON.stringify(data.value)).subscribe(response => {
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
