import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-client-management',
  templateUrl: './client-management.component.html',
  styleUrls: ['./client-management.component.css']
})
export class ClientManagementComponent implements OnInit {

  isUser = false;
  isChecker = false;
  isApprover = false;
  type = '';
  LglNm = '';
  client_type = '';
  for_devision = '';
  selectedCountry = '';
  isView = false;
  results;
  data = [];
  materials = [];
  selectedClient = [];
  software_type: any;
  plant_type: any;
  plant_id: any;

  constructor(private service: DataAccessService, private router: Router) {
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));

    this.software_type = this.service.getPlantConfigFields('software_type');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    if (this.software_type == null) {
      this.service
        .getData(
          'https://gmpsoftwareindia.com/admin/api/clients/client_data_without_token.php?type=get_client_data_by_id&id=' +
            localStorage.getItem('plant_id')
        )
        .subscribe((response) => {
          localStorage.setItem('client_info', JSON.stringify(response));
          this.software_type =
            this.service.getPlantConfigFields('software_type');
        });
    }
  }

  ngOnInit() {
    this.getclientlist();
    this.filterMaterial();
    this.get_rights();
  }

  getclientlist() {
    this.service
      .get('training.php?type=getClientsLog')
      .subscribe((response: any) => {
        this.data = response;
        console.log('mahi', this.data);
      });
  }

  view(index) {
    this.selectedClient = this.materials[index];
    this.isView=true
  }

  updateClient(status) {
    this.service
      .get(
        'marketing/client.php?type=updateClient&status=' +
          status +
          '&id=' +
          this.selectedClient['id']
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Client Updated Successfully');
          this.isView = false;
          this.getclientlist();
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }

  edit(id) {
    this.router.navigate(['/clients/edit/' + id]);
  }

  filterMaterial() {
    this.data = [];
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (
        material['type'].toUpperCase().includes(this.type.toUpperCase()) &&
        material['client_type']
          .toUpperCase()
          .includes(this.client_type.toUpperCase()) &&
        material['LglNm'].toUpperCase().includes(this.LglNm.toUpperCase())
      ) {
        this.data[this.data.length] = material;
      }
    }
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

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id')
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





}
