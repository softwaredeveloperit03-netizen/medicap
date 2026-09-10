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

  analysislist = [];
    flag=false
  constructor(private service: DataAccessService, private router: Router){ }

  ngOnInit(): void {
  }

  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.analysislist[this.analysislist.length] = temp;
    console.log(this.analysislist);
    data.resetForm();
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['analysislist']=this.analysislist;

    this.service.post('hr/achievement.php?type=saveRinse', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.analysislist = [];
        this.router.navigate(['/microbiology/rinse']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  delData(index) {
    this.analysislist.splice(index, 1);
  }

  onValueChange(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);
  
    if (isValidBatchNo) {
      this.flag=false
    } else {

      this.flag=true
    }
  }

 
}
