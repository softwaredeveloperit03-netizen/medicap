import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-line',
  templateUrl: './line.component.html',
  styleUrls: ['./line.component.css']
})
export class LineComponent implements OnInit {

  isNew = false;
  results;

  selectedSampling = [];
  checkpoints = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingLineClearance();
  }

  getPendingLineClearance() {
    this.service.get('qc/sampling/finish.php?type=getPendingLineClearance').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    if (this.selectedSampling['specification_no'] == '') {
      alertify.success('Specification Not Available');
    } else {
      this.isNew = true;
      if (this.selectedSampling['clearance_status'] == 'pending') {
        this.getSamplingCheckpoints();
      }
    }
  }

  getSamplingCheckpoints() {
    this.service.get('qa/clearance.php?type=getQCSamplingCheckpoints').subscribe((response: any) => {
      this.checkpoints = response;
    });
  }

  callforclearance(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.selectedSampling['checkpoints'] = this.checkpoints;
    this.service.post('qc/sampling/finish.php?type=callforclearance', JSON.stringify(this.selectedSampling)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Line Clearance Request send to QA Department');
        this.isNew = false;
        this.getPendingLineClearance();
        this.checkpoints = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
