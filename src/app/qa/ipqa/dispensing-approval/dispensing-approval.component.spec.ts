import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DispensingApprovalComponent } from './dispensing-approval.component';

describe('DispensingApprovalComponent', () => {
  let component: DispensingApprovalComponent;
  let fixture: ComponentFixture<DispensingApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DispensingApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DispensingApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
