import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SecurAccessCtrlComponent } from './secur-access-ctrl.component';

describe('SecurAccessCtrlComponent', () => {
  let component: SecurAccessCtrlComponent;
  let fixture: ComponentFixture<SecurAccessCtrlComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SecurAccessCtrlComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SecurAccessCtrlComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
