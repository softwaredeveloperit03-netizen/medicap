import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;
@Component({
  selector: 'app-mannitol',
  templateUrl: './mannitol.component.html',
  styleUrls: ['./mannitol.component.css'],
})
export class MannitolComponent implements OnInit {
  selectedEquipment: string;
  equipment_name: string;
  department_name: string;
  equipmentArr = [];


   
 


  constructor(private service: DataAccessService, private router: Router) {}
  safty_data = [];
  disposal: string =
    'Unused Reference Solutions can be disposed into the laboratory.';

  Storage: string =
    'Sample tubes of Reference Solution A and Reference Solution B are Stored at 5°C ± 3°C  for inventigation purpose.';

  Expiry: string =
    'Referance Solution A and Reference Solution B are to be used within 24 hours of preparation.';


    safty ="Refer To SOP-)87 For General Information For Safty In The Qc Laboratory";
  materials = [
    {
      name: 'Mannitol Secoundary Standard',
      assay: '',
      batchNo: '',
      vialNo: '',
      expiryDate: '',
    }
  ];

  reagents = [
    {
      reagent: 'Milli-Q Water',
      dateOpened: '',
      manufacturer: '',
      batchNo: '',
      expiryDate: '',
    },
    {
      reagent: 'HPLC Grade Water',
      dateOpened: '',
      manufacturer: '',
      batchNo: '',
      expiryDate: '',
    },
    {
      reagent: 'Water for Injection (WFI)',
      dateOpened: '',
      manufacturer: '',
      batchNo: '',
      expiryDate: '',
    },
  ];

  solution_aDuplicate = [
    {
      particular:
        'Place a clean weighing boat on the balance, tare. weigh accurately 0.5g of Mannitol secoundary standard. Tare again',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Transfer the Mannitol to a 10Ml  Volumetric flask. Replace weighing boat on the balance, and record the negative weight',
      date: '',
      analyst: '',
      otherInfo: [
        {
          cali_date: "",
          flask_id: "",
          standard_weight: "",
          srNo: "(a)"
        },
        {
          cali_date: "",
          flask_id: "",
          standard_weight: "",
          srNo: "(b)"
        }
      ],
    },
    {
      particular:
        'Add approximately 5ml water, shake until the Minnitol has completely dissolved.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular: 'Add water to the 10ml mark. Mix thoroughtly',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Filter Reference Solution A into HPLC vial discarding the first 2ml of the filotrate and the remaining solution into a 10ml sample tube.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Ensure volumetric flasks, vials and sample tubes are labelled in according with SOP-087 and enter expriry date and time on page 1',
      date: '',
      analyst: '',
      otherInfo: [],
    },
  ];


  solution_a = [
    {
      particular:
        'Place a clean weighing boat on the balance, tare. weigh accurately 0.5g of Mannitol secoundary standard. Tare again',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Transfer the Mannitol to a 10Ml  Volumetric flask. Replace weighing boat on the balance, and record the negative weight',
      date: '',
      analyst: '',
      otherInfo: [
        {
          cali_date: "",
          flask_id: "",
          standard_weight: "",
          srNo: "(a)"
        },
        {
          cali_date: "",
          flask_id: "",
          standard_weight: "",
          srNo: "(b)"
        }
      ],
    },
    {
      particular:
        'Add approximately 5ml water, shake until the Minnitol has completely dissolved.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular: 'Add water to the 10ml mark. Mix thoroughtly',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Filter Reference Solution A into HPLC vial discarding the first 2ml of the filotrate and the remaining solution into a 10ml sample tube.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
        'Ensure volumetric flasks, vials and sample tubes are labelled in according with SOP-087 and enter expriry date and time on page 1',
      date: '',
      analyst: '',
      otherInfo: [],
    },
  ];

  solution_bDuplicate = [
    {
      particular:'Pipette 2.0mL of Reference Solution A into a 100mL volumetric flaks ',
      date: '',
      analyst: '',
      otherInfo: [
        {
          pipe_id: "",
          Pipe_cali_date: "",
          flask_id: "",
          flask_cali_date: "",
          srNo: "(a)"
        },
        {
          pipe_id: "",
          Pipe_cali_date: "",
          flask_id: "",
          flask_cali_date: "",
          srNo: "(b)"
        }
      ],
    },
    {
      particular: 'Add water to the 10ml mark. Mix thoroughtly',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:'Filter Reference Solution A into HPLC vial discarding the first 2ml of the filotrate and the remaining solution into a 10ml sample tube.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
'Ensure volumetric flaks, vials and sample tubes are labelled in accordance with SOP-087 and enter expiry date and time on page 1',
      date: '',
      analyst: '',
      otherInfo: [],
    },
  ];

  solution_b = [
    {
      particular:'Pipette 2.0mL of Reference Solution A into a 100mL volumetric flaks ',
      date: '',
      analyst: '',
      otherInfo: [
        {
          pipe_id: "",
          Pipe_cali_date: "",
          flask_id: "",
          flask_cali_date: "",
          srNo: "(a)"
        },
        {
          pipe_id: "",
          Pipe_cali_date: "",
          flask_id: "",
          flask_cali_date: "",
          srNo: "(b)"
        }
      ],
    },
    {
      particular: 'Add water to the 10ml mark. Mix thoroughtly',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:'Filter Reference Solution A into HPLC vial discarding the first 2ml of the filotrate and the remaining solution into a 10ml sample tube.',
      date: '',
      analyst: '',
      otherInfo: [],
    },
    {
      particular:
'Ensure volumetric flaks, vials and sample tubes are labelled in accordance with SOP-087 and enter expiry date and time on page 1',
      date: '',
      analyst: '',
      otherInfo: [],
    },
  ];

  ngOnInit(): void {
    this.getEquipments();
    this.getemployee();
    this.getVolumetricSolutions();
    this.getGlassware();
     
  }



solutions;
GlassWares;

  getVolumetricSolutions() {
    this.service.get('qc/volumetric.php?type=getVolumetricSol&solType=manitol').subscribe(response => {
      this.solutions = response;
    });
  }
  getGlassware() {
    this.service.get('qc/volumetric.php?type=getGlassware').subscribe(response => {
      this.GlassWares = response;
    });
  }


  weigh_slip:File;
  onFileChanged(event){
    this.weigh_slip=event.target.files[0];
  }
  



  // save(data) {


  //   let temp = data.value;
  //   temp['solution_b'] = this.Final_solution_b;
  //   temp['solution_a'] = this.Final_solution_a;
  //   temp['equipmentArr'] = this.equipmentArr;
  //   temp['reagents'] = this.reagents;
  //   temp['materials'] = this.materials;
  //   console.log(temp);
  // }

  sol_type ="manitol";

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp=data.value;

    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
 
     uploadData.append('solution_b', JSON.stringify(this.Final_solution_b));
     uploadData.append('solution_a', JSON.stringify(this.Final_solution_a));
     uploadData.append('Equipment', JSON.stringify(this.equipmentArr));
     uploadData.append('Reagent', JSON.stringify(this.reagents));
     uploadData.append('standard_details', JSON.stringify(this.materials));
     uploadData.append('sol_type', this.sol_type);
 

    if (this.weigh_slip !== undefined) {
      uploadData.append('weigh_slip', this.weigh_slip, this.weigh_slip.name);
    }


    this.service.post('qc/volumetric.php?type=saveVolumetricPreparation', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
       
        this.router.navigate(['/qc/volumetric']);

       } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }
   

  
  employee;

  
  getemployee() {
    this.service.get('qc/volumetric.php?type=getemployees').subscribe(response => {
      this.employee = response;
    });
  }




  equipmentData: any;
  getEquipments() {
    this.service
      .get(
        'master/equipment.php?type=getEquipments' +
          '&equipment_name=' +
          this.equipment_name +
          '&department_name=' +
          this.department_name
      )
      .subscribe((response) => {
        this.equipmentData = response;
      });
  }
  tag_no;
  date;
  equipmentdetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.tag_no = this.equipmentData[index]?.tag_no;
    }
  }
  addEquipments(data) {
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    let temp = data.value;
    temp['tag_no'] = this.tag_no;
    temp['date'] = this.date;
    this.equipmentArr.push(temp);
    data.reset();
    this.tag_no = '';
    this.date = '';
  }
  delequip(index) {
    this.equipmentArr.splice(index, 1);
  }



  Final_solution_a=[];



  AddSolA(){




   // Create a deep copy of solution_a
   const solutionACopy = JSON.parse(JSON.stringify(this.solution_a));

   // Push the deep copy to Final_solution_a
   this.Final_solution_a.push(solutionACopy);

   // Reset solution_a to a deep copy of solution_aDuplicate
   this.solution_a = JSON.parse(JSON.stringify(this.solution_aDuplicate));

   console.log(this.Final_solution_a);
  }

  Final_solution_b=[];



  AddSolB(){

  
       // Create a deep copy of solution_a
   const solutionACopy = JSON.parse(JSON.stringify(this.solution_b));

   // Push the deep copy to Final_solution_b
   this.Final_solution_b.push(solutionACopy);

   // Reset solution_a to a deep copy of solution_aDuplicate
   this.solution_b = JSON.parse(JSON.stringify(this.solution_bDuplicate));

   console.log(this.Final_solution_b);
 
  }





















}
