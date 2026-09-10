import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppofdeviatioqaComponent } from './appofdeviatioqa.component';

describe('AppofdeviatioqaComponent', () => {
  let component: AppofdeviatioqaComponent;
  let fixture: ComponentFixture<AppofdeviatioqaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AppofdeviatioqaComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AppofdeviatioqaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
