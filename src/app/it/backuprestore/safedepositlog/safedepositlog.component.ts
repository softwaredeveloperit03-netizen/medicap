import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-safedepositlog',
  templateUrl: './safedepositlog.component.html',
  styleUrls: ['./safedepositlog.component.css']
})
export class SafedepositlogComponent implements OnInit {
  isNew = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getit_safe_deposite_locker();
  }
results;
  safedeposit(){
    this.isNew = true;
  }
      getit_safe_deposite_locker() {
    this.service
      .get('it/EQbackup.php?type=getit_safe_deposite_locker')
      .subscribe((response) => {
        this.results = response;
      });
  }
  save(data){
  let temp=data.value
        console.log('temp :>> ', temp);
      this.service.post('it/EQbackup.php?type=saveit_safe_deposite_locker',JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
          this.getit_safe_deposite_locker();
           this.isNew = false;
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
    }
}
