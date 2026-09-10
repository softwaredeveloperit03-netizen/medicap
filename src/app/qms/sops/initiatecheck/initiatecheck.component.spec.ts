import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InitiatecheckComponent } from './initiatecheck.component';

describe('InitiatecheckComponent', () => {
  let component: InitiatecheckComponent;
  let fixture: ComponentFixture<InitiatecheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InitiatecheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InitiatecheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
