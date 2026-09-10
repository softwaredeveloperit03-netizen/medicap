import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AuthorizedlogComponent } from './authorizedlog.component';

describe('AuthorizedlogComponent', () => {
  let component: AuthorizedlogComponent;
  let fixture: ComponentFixture<AuthorizedlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AuthorizedlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AuthorizedlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
