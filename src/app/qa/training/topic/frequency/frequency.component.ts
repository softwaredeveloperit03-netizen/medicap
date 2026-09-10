import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-frequency',
  templateUrl: './frequency.component.html',
  styleUrls: ['./frequency.component.css']
})
export class FrequencyComponent implements OnInit {

   
  levelData;
 
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
     this.getfrequencyLevel();
  }

 
  getfrequencyLevel() {
    this.service.get('training.php?type=getfrequencyLevel').subscribe(response => {
        this.levelData = response;
      });
  }
  
   

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
    this.service.post('training.php?type=saveLevelFrequency', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        data.reset();
        this.getfrequencyLevel();
       } else {
        alert('Failed: An error occured, please try again!');
      }
    });
 
  }


 

  

   

}
