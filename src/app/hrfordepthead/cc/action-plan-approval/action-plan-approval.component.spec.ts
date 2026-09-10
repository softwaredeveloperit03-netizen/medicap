import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ACTIONPLANAPPROVALComponent } from './action-plan-approval.component';

describe('ACTIONPLANAPPROVALComponent', () => {
  let component: ACTIONPLANAPPROVALComponent;
  let fixture: ComponentFixture<ACTIONPLANAPPROVALComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ACTIONPLANAPPROVALComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ACTIONPLANAPPROVALComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
