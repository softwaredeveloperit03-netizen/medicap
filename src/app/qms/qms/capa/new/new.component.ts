import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getPendingCAPA();
  }

  getPendingCAPA() {
    this.service.get('qa/capa.php?type=getPendingCAPA').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service.post('qa/capa.php?type=saveCAPA', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingCAPA();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  saveCAPA(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('capa.php?type=savecapa', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.router.navigate(['../capa']);
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
