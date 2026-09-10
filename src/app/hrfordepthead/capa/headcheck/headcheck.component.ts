import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-headcheck',
  templateUrl: './headcheck.component.html',
  styleUrls: ['./headcheck.component.css']
})
export class HeadcheckComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  remark = '';
  comment='';
  plant_id:any;
  qa_head_remark='';
  categories;
  new_capa_dtl;
close_cmt_capa_hod;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessCapa();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
 
  }
  
 
  getInprocessCapa(){
    this.service
      .get(
        'qms/capa2.php?type=getcheckedSevenStepCAPA&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true; 
  }
desc_immidiateAction
reason_first_alt_tcd;
close_cmt_capa_QAHEAD;
reason_Second_alt_tcd;
   update(value) {
    let temp={}
    this.service.post('qms/capa2.php?type=checkSevenStepByDeptHead&capa_no='+this.selectedDev['capa_no'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessCapa();
        this.isView = false;
        alertify.success('Capa Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  viewFile1(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }
  viewFile2(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }
  viewFile3(url) {
    url = this.service.url + '../../upload/capa/' + url;
    window.open(url, '_blank');
  }

}
