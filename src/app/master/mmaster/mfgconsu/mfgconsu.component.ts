import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-mfgconsu',
  templateUrl: './mfgconsu.component.html',
  styleUrls: ['./mfgconsu.component.css']
})
export class MfgconsuComponent implements OnInit {

  loading = false;
  constructor(public service: DataAccessService, private router: Router) { }

  
  ngOnInit(): void {
     this.get_fg_api_products();
  }

  fg_api_products;
  get_fg_api_products(){
    this.service.get('master/materialtype.php?type=get_fg_api_products').subscribe(response => {
      this.fg_api_products = response
    })
  }

  fg_control_reserve_type = 'Not Applicable';

  saveFgData(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;

    this.service.post('master/materialtype.php?type=saveFGtype', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.get_fg_api_products();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  deleteFgType(row: any) {
    if (!row || !row.id) {
      alertify.error('Cannot delete: missing id');
      return;
    }
    if (!confirm('Delete this FG Type entry?')) {
      return;
    }
    this.service
      .get('master/materialtype.php?type=deleteFGtype&id=' + encodeURIComponent(String(row.id)))
      .subscribe({
        next: (response: any) => {
          if (response && response.status === 'success') {
            alertify.success('Deleted');
            this.get_fg_api_products();
          } else {
            alertify.error((response && response.status) || 'Delete failed');
          }
        },
        error: () => alertify.error('Delete failed'),
      });
  }


}
