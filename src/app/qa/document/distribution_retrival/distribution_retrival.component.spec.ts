/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { Distribution_retrivalComponent } from './distribution_retrival.component';

describe('Distribution_retrivalComponent', () => {
  let component: Distribution_retrivalComponent;
  let fixture: ComponentFixture<Distribution_retrivalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ Distribution_retrivalComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(Distribution_retrivalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
