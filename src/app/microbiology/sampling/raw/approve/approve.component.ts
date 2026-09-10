import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {
  isView = false;
  results;

  selectedSampling = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckedSamplings();
  }

  getCheckedSamplings() {
    this.service.get('qc/sampling/raw.php?type=getCheckedSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
  }

  updateSampling(status) {
    let temp = {};
    temp["sampling_no"] = this.selectedSampling['sampling_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["specification_no"] = this.selectedSampling['specification_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["material_code"] = this.selectedSampling['material_code'];
    this.service.post('qc/sampling/raw.php?type=updateCheckedSampling&status=' + status + '&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getCheckedSamplings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
