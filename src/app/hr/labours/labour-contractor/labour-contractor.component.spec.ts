import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LabourContractorComponent } from './labour-contractor.component';

describe('LabourContractorComponent', () => {
  let component: LabourContractorComponent;
  let fixture: ComponentFixture<LabourContractorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LabourContractorComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LabourContractorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
