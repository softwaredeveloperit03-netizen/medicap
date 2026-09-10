import { Component, OnInit } from '@angular/core';
import {FormGroup,FormBuilder,Validator} from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  
  letter_type;
 





  constructor(private service: DataAccessService,private router: Router) { }
  ngOnInit() {
  }

save(letter){
  this.service.post('hrDepartment.php?type=saveLetter',letter).subscribe(response => {
    if (response['status'] === 'success') {
      alertify.success('Data Save Successfully !!!');
      this.router.navigate(['/hr/labours']);
      // data.resetForm();
    } else {
      alertify.error('Please Try Again');
    }
  });
}
}
