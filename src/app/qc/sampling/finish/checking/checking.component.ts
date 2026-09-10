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

  selectedSampling = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getActiveSamplings();
  }

  getActiveSamplings() {
    this.service.get('qc/sampling/finish.php?type=getActiveSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
  }

  updateSampling(status) {
    this.service.get('qc/sampling/finish.php?type=updateActiveSampling&status=' + status + '&id=' + this.selectedSampling['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getActiveSamplings();
      }else{
        alertify.error("some error Ocuured");
      }

    });
  }

}
