/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { Other_deptComponent } from './other_dept.component';

describe('Other_deptComponent', () => {
  let component: Other_deptComponent;
  let fixture: ComponentFixture<Other_deptComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ Other_deptComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(Other_deptComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
