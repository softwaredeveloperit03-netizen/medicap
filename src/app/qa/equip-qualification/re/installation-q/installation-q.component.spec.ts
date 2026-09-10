import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InstallationQComponent } from './installation-q.component';

describe('InstallationQComponent', () => {
  let component: InstallationQComponent;
  let fixture: ComponentFixture<InstallationQComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InstallationQComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InstallationQComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
