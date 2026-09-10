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
  flag=false
    flag1=false

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  add(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp=Form.value
    this.service.post('hr/achievement.php?type=saveSwab', JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success'){
        this.router.navigate(['/microbiology/swap']);
        alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
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

  onValueChange1(newValue: string) {
    const batchNo = /^\d+$/;
    const isValidBatchNo = batchNo.test(newValue);
  
    if (isValidBatchNo) {
      this.flag1=false
    } else {

      this.flag1=true
    }
  }


}
