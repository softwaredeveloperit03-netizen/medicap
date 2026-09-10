import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  
  isView = false;
  results;
  trainers;


  selectedNeed = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRetrainings();
    this.getTrainers();

  }

  getPendingRetrainings() {
    this.service.get('training.php?type=getPendingRetrainingsEHS').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedNeed = this.results[index];
    this.isView = true;
  }


  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.trainers = response;
    });
  }

  save() {
    this.service.post('training.php?type=saveRetraining', JSON.stringify(this.selectedNeed)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('successfully saved');
        this.getPendingRetrainings();
        this.isView = false;
      } else {
        alertify.error(response['msg']);
      }
    });
  }

}
