import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html'
})
export class ApproveComponent implements OnInit {
  data;
  selectedForm;
  selectedRelated;
  departments;
  formview = false;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getPendingChangeControl();
  }

  getPendingChangeControl() {
    this.service.get('capa.php?type=getpendingcapa').subscribe(response => {
      this.data = response;
    });
  }

  viewForm(index) {
    this.selectedForm = this.data[index];
    this.selectedRelated = this.data[index].change_related;
    this.formview = true;
  }
  approve(){
    alert('Approve Sucessfully');
    this.formview = false;
  }
  reject(){
    alert('Reject Successfully');
    this.formview = false;
  }
}
