import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UsrreqformComponent } from './usrreqform.component';

describe('UsrreqformComponent', () => {
  let component: UsrreqformComponent;
  let fixture: ComponentFixture<UsrreqformComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UsrreqformComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UsrreqformComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
