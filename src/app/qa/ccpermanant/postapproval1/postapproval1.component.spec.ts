import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Postapproval1Component } from './postapproval1.component';

describe('Postapproval1Component', () => {
  let component: Postapproval1Component;
  let fixture: ComponentFixture<Postapproval1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Postapproval1Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Postapproval1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
