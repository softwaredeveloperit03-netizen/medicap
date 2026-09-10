import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  selectedTraining=[];
  results;
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getOnJobTraining();
  }

  getOnJobTraining(){
    this.service.get('qa/training.php?type=getOnJobTraining').subscribe(response => {
      this.results= response;
    })
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

update(status){
  this.service.get('qa/training.php?type=updateOnJobtraining&status=' + status +'&id=' + this.selectedTraining['id']).subscribe(response =>{
    if (response['status'] == 'success') {
      alertify.success('Record Updated successfully');
      this.isView=false;
      this.getOnJobTraining();
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  })
}

}
