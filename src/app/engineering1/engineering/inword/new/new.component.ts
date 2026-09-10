import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService) { }

  ngOnInit() {
  }

  saveSpareInword(data) {
    if(!data.valid){
      alertify.error('All field are required');
      return;
    }
    this.service.post('engineering/inword.php?type=saveSpareInword',JSON.stringify(data.value)).subscribe(response =>{
      if(response['status']=='success'){
        data.resetForm();
        alertify.success('Record Inserted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
