import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ActivatedRoute, Params, } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  today = '';
  preparation= [];
  prepared_by;
  constructor(private service: DataAccessService,public route: ActivatedRoute, private router: Router, private datePipe: DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
  }
 
  saveEntry(data) {
    if(!data.valid){
      alertify.error('all feilds are required');
      return;
    }
  //    this.service.post('master/checklist.php?type=SaveMastercheckList', JSON.stringify(data.value)).subscribe(response => {
  //   if (response['status'] == 'success') {
  //     alert('Saved Successfully');
  //     this.preparation = [];
  //     // this.router.navigate(['/checklist']);
  //   } else {
  //     console.log(response);
  //     alert('Failed: An error occured, please try again!');
  //   }
  // });
}
}
