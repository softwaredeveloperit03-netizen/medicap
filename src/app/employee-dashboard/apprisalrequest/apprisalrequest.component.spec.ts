import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ApprisalrequestComponent } from './apprisalrequest.component';

describe('ApprisalrequestComponent', () => {
  let component: ApprisalrequestComponent;
  let fixture: ComponentFixture<ApprisalrequestComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ApprisalrequestComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ApprisalrequestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
