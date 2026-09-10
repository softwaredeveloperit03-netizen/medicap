import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SeeoffComponent } from './seeoff.component';

describe('SeeoffComponent', () => {
  let component: SeeoffComponent;
  let fixture: ComponentFixture<SeeoffComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SeeoffComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SeeoffComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
