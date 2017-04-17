%define name docutron
Summary: Docutron Document Management System
Name: %{name}
Version: 3.1
Release: 2.SLES
License: Docutron
Group: Docutron
BuildArch: noarch
BuildRoot: /var/tmp/%{name}-root
Requires: XFree86, apache2-prefork, cups, grep, mkisofs, mysql, mysql-client,
Requires: net-tools, php4, php4-mysql, php4-pear, php4-session, php4-sockets,
Requires: procps, samba, util-linux, wget, zip, tiff, netpbm, ghostscript, 
Requires: ghostscript-library, ghostscript-fonts-std, ghostscript-fonts-other
Requires: apache2-mod_php4
Packager: Tristan McCann <tristan@docutronsystems.com>

%description
Docutron Document Management System

%prep
rm -rf %{name}
mkdir %{name}
cd %{name}
export CVSROOT="docutron@mail.docutronsystems.com:/home/cvsroot"
export CVS_RSH="ssh"
cvs co -r r3_1 html

%build
rm -rf `find | grep CVS`

%install
rm -rf %{buildroot}
mkdir -p %{buildroot}/opt/docutron/htdocs
mkdir -p %{buildroot}/opt/docutron/lib
mkdir -p %{buildroot}/opt/docutron/share/sql
mkdir -p %{buildroot}/etc/opt/docutron
mkdir -p %{buildroot}/etc/cron.d
mkdir -p %{buildroot}/etc/apache2/conf.d
mkdir -p %{buildroot}/var/opt/docutron/client_files/indexing
mkdir -p %{buildroot}/var/opt/docutron/client_files/thumbs
mkdir -p %{buildroot}/var/opt/docutron/client_files/inbox
mkdir -p %{buildroot}/var/opt/docutron/client_files/personalInbox/admin
cp -a %{name}/html/* %{buildroot}/opt/docutron/htdocs
#rm -rf %{buildroot}/opt/docutron/htdocs/install
cp -f %{name}/html/install/conf/SUSEDMS.DEFS %{buildroot}/etc/opt/docutron/DMS.DEFS
chmod 0644 %{buildroot}/etc/opt/docutron/DMS.DEFS
cp %{name}/html/install/conf/docutron.conf %{buildroot}/etc/apache2/conf.d
chmod 0644 %{buildroot}/etc/apache2/conf.d/docutron.conf
cp %{name}/html/install/sql/*sql %{buildroot}/opt/docutron/share/sql
cp %{name}/html/install/conf/docutron.cron %{buildroot}/etc/cron.d/docutron
chmod 0644 %{buildroot}/etc/cron.d/docutron
cp %{name}/html/install/conf/rpminstallDB.php %{buildroot}/opt/docutron/share/installDB.php
cp %{name}/html/install/installSambaRPM.sh %{buildroot}/opt/docutron/share/installSamba.sh
cp %{name}/html/install/conf/susesettings.php %{buildroot}/opt/docutron/htdocs/lib/settings.php
chmod -R 0644 %{buildroot}/opt/docutron
chmod -R a+X %{buildroot}/opt/docutron
chmod -R 0666 %{buildroot}/var/opt/docutron
chmod -R a+X %{buildroot}/var/opt/docutron

%post
[ -a /etc/init.d/apache2 ] && /etc/init.d/apache2 restart

%postun
[ -a /etc/init.d/apache2 ] && /etc/init.d/apache2 restart

%clean
rm -rf %{buildroot}

%files
%attr(-,root,root) /opt/docutron
%attr(-,wwwrun,www) /var/opt/docutron
%config /etc/opt/docutron/DMS.DEFS
%config /etc/apache2/conf.d/docutron.conf
%config /etc/cron.d/docutron

%changelog
* Mon Nov 29 2004 Tristan McCann <tristan@docutronsystems.com>
- Initial packaging.
