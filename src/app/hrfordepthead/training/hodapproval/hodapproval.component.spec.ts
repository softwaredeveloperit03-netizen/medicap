import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HodapprovalComponent } from './hodapproval.component';

describe('HodapprovalComponent', () => {
  let component: HodapprovalComponent;
  let fixture: ComponentFixture<HodapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HodapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HodapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
