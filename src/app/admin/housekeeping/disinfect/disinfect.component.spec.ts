import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DisinfectComponent } from './disinfect.component';

describe('DisinfectComponent', () => {
  let component: DisinfectComponent;
  let fixture: ComponentFixture<DisinfectComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DisinfectComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DisinfectComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
