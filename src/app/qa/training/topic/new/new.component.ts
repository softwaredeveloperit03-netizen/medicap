import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  
  category_data;
 
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
      // this.getTrainingCategory();
  }

 
  getTrainingCategory() {
    this.service.get('training.php?type=getTrainingCategory')
      .subscribe(response => {
        this.category_data = response;
      });
  }
  
   

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
    this.service.post('training.php?type=addsubjects', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.reset();
       } else {
        alert('Failed: An error occured, please try again!');
      }
    });
 
  }



  isCategory  = false;
  addCat(value){
    if(value == 'ADD NEW'){
      this.isCategory = true;
    }
  }

 
  saveCategory(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('training.php?type=save_training_category', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.reset();
        this.isCategory = false;
        this.getTrainingCategory();
       } else {
        alert('Failed: An error occured, please try again!');
      }
    });
 
  }

   

}
