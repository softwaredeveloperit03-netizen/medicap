import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  isView = false;
  results = [];

  selectedResult = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getReviewCAPA();
  }

  getReviewCAPA() {
    this.service.get('qa/capa.php?type=getReviewCAPA').subscribe((response:any) => {
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
    temp["id"] = this.selectedResult['id'];
    temp['capa_no'] = this.selectedResult["capa_no"];
    this.service.post('qa/capa.php?type=saveDeptReview', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getReviewCAPA();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
