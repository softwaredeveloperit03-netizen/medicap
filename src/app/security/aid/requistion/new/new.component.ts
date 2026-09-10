
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify : any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }


  saveStock(data){
    if(!data.valid){
      alertify.error("all field are required");
      return;
    }
    let temp = data.value;
    this.service.post('security/firstaid.php?type=saveRequisition', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Data Added Successfully');
        data.resetForm();
        this.router.navigate(['/security/aid/requistion']);
      } else {
       alertify.error('An error occured');
      }
    });
  }

}
