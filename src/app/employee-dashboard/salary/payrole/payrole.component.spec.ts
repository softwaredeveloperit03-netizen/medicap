/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { PayroleComponent } from './payrole.component';

describe('PayroleComponent', () => {
  let component: PayroleComponent;
  let fixture: ComponentFixture<PayroleComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PayroleComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PayroleComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
