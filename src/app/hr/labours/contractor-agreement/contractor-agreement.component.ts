import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-contractor-agreement',
  templateUrl: './contractor-agreement.component.html',
  styleUrls: ['./contractor-agreement.component.css']
})
export class ContractorAgreementComponent implements OnInit {
  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  isApprover;
  list;
  list1:any;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getContractorlist1();
    this.getContractorlist();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

     this.get_rights();
  }
  
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }


  

  selectedcontractor;
  onChange(event) {

    let ind = event.target.selectedIndex;
    this.selectedcontractor = this.list1[ind-1];
  }

 
  open(url) {
    url = this.service.url + '../../upload/contractor/doc/' + url;
    window.open(url, '_blank');
  }


  getContractorlist() {
    this.service.get('admin.php?type=getApprovedContractoragreement').subscribe(response => {
      this.list = response;
    });
  }

  getContractorlist1() {
    this.service.get('admin.php?type=getcontractor').subscribe(response => {
      this.list1 = response;
    });
  }

  address;
  pincode;
  email;
  phone_no;

  onClick(index){

    this.selectedcontractor = this.list1[index];
    this.address = this.selectedcontractor['address'];
    this.pincode = this.selectedcontractor['pincode'];
    this.phone_no = this.selectedcontractor['phone_no'];
    this.email = this.selectedcontractor['email'];

  }

  selectedFile: File;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }

   saveForm(data) {
      const formData = new FormData();

      if (this.selectedFile !== undefined) {
        formData.append('bond', this.selectedFile, this.selectedFile.name);
      } 
        let cid = this.selectedcontractor['id'];

      this.service.post('admin.php?type=addcontract&cid='+cid, formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
           alert('Successfully Added Contract');
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }

  close() {
    this.router.navigate(['/hr']);
  }

}
