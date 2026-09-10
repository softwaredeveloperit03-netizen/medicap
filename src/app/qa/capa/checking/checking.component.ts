import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  remark = '';
  comment='';
  plant_id:any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessCapa();
    this.plant_id = this.service.getPlantConfigFields('plant_id');

  }

  getInprocessCapa(){
    this.service.get('qms/capa.php?type=getPendingCAPA').subscribe(response => {
      this.results = response;
    });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  update(value) {
    this.service.get('qms/capa.php?type=checkCAPA&status=' + value+'&capa_no='+this.selectedDev['capa_no']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessCapa();
        this.isView = false;
        alertify.success('Capa Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  viewfile(url) {
    url = this.service.url +url;
    window.open(url, '_blank');
  }

}
